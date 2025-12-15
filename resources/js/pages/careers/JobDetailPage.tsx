import { type ReactNode } from 'react';
import { MapPin, Clock, Building2, Calendar, ArrowLeft, Briefcase, Mail } from 'lucide-react';
import { Link } from '@inertiajs/react';
import Layout from '../../components/layout/Layout';
import { JobPosting } from '../../types';

interface JobDetailPageProps {
  job: JobPosting;
  relatedJobs: JobPosting[];
}

const employmentTypeLabels: Record<string, string> = {
  full_time: 'Full Time',
  part_time: 'Part Time',
  contract: 'Contract',
  internship: 'Internship',
};

const JobDetailPage = ({ job, relatedJobs }: JobDetailPageProps) => {
  const formatDate = (dateString?: string) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  };

  const isDeadlinePassed = (deadline?: string) => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
  };

  const daysUntilDeadline = (deadline?: string) => {
    if (!deadline) return null;
    const deadlineDate = new Date(deadline);
    const now = new Date();
    const days = Math.ceil((deadlineDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    return days > 0 ? days : null;
  };

  const handleApply = () => {
    const subject = encodeURIComponent(`Application for ${job.title}`);
    const body = encodeURIComponent(`Dear Hiring Team,\n\nI am writing to express my interest in the ${job.title} position.\n\n[Please attach your CV and cover letter]\n\nBest regards`);
    window.location.href = `mailto:careers@eduboutique.co.zw?subject=${subject}&body=${body}`;
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      <div className="bg-primary-700 text-white py-12">
        <div className="container-custom">
          <Link href="/careers" className="inline-flex items-center text-primary-200 hover:text-white mb-4 transition-colors">
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to all positions
          </Link>
          <h1 className="text-3xl md:text-4xl font-bold mb-4">{job.title}</h1>
          <div className="flex flex-wrap gap-4 text-primary-100">
            {job.department && (
              <div className="flex items-center gap-2">
                <Building2 className="w-5 h-5" />
                <span>{job.department}</span>
              </div>
            )}
            {job.location && (
              <div className="flex items-center gap-2">
                <MapPin className="w-5 h-5" />
                <span>{job.location}</span>
              </div>
            )}
            <div className="flex items-center gap-2">
              <Clock className="w-5 h-5" />
              <span>{employmentTypeLabels[job.employment_type] || job.employment_type}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="container-custom py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow-sm p-6 md:p-8">
              {job.description && (
                <section className="mb-8">
                  <h2 className="text-xl font-semibold mb-4">About this role</h2>
                  <div
                    className="prose prose-gray max-w-none"
                    dangerouslySetInnerHTML={{ __html: job.description }}
                  />
                </section>
              )}

              {job.requirements && (
                <section className="mb-8">
                  <h2 className="text-xl font-semibold mb-4">Requirements</h2>
                  <div
                    className="prose prose-gray max-w-none"
                    dangerouslySetInnerHTML={{ __html: job.requirements }}
                  />
                </section>
              )}

              <section>
                <h2 className="text-xl font-semibold mb-4">How to Apply</h2>
                <p className="text-gray-600 mb-4">
                  Interested candidates should send their CV and cover letter to our HR department.
                  Please include the job title in your email subject line.
                </p>
                <button
                  onClick={handleApply}
                  className="btn-primary inline-flex items-center gap-2"
                  disabled={isDeadlinePassed(job.deadline)}
                >
                  <Mail className="w-5 h-5" />
                  Apply via Email
                </button>
              </section>
            </div>
          </div>

          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-sm p-6 sticky top-4">
              <h3 className="font-semibold mb-4">Job Details</h3>

              <div className="space-y-4">
                <div>
                  <p className="text-sm text-gray-500">Employment Type</p>
                  <p className="font-medium">{employmentTypeLabels[job.employment_type] || job.employment_type}</p>
                </div>

                {job.department && (
                  <div>
                    <p className="text-sm text-gray-500">Department</p>
                    <p className="font-medium">{job.department}</p>
                  </div>
                )}

                {job.location && (
                  <div>
                    <p className="text-sm text-gray-500">Location</p>
                    <p className="font-medium">{job.location}</p>
                  </div>
                )}

                {job.experience_level && (
                  <div>
                    <p className="text-sm text-gray-500">Experience Level</p>
                    <p className="font-medium capitalize">{job.experience_level}</p>
                  </div>
                )}

                {job.published_at && (
                  <div>
                    <p className="text-sm text-gray-500">Posted</p>
                    <p className="font-medium">{formatDate(job.published_at)}</p>
                  </div>
                )}

                {job.deadline && (
                  <div>
                    <p className="text-sm text-gray-500">Application Deadline</p>
                    <p className="font-medium">{formatDate(job.deadline)}</p>
                    {daysUntilDeadline(job.deadline) && (
                      <p className={`text-sm ${daysUntilDeadline(job.deadline)! <= 7 ? 'text-orange-600' : 'text-gray-500'}`}>
                        {daysUntilDeadline(job.deadline)} days remaining
                      </p>
                    )}
                    {isDeadlinePassed(job.deadline) && (
                      <p className="text-sm text-red-600">Applications closed</p>
                    )}
                  </div>
                )}
              </div>

              <hr className="my-6" />

              <button
                onClick={handleApply}
                className="btn-primary w-full justify-center"
                disabled={isDeadlinePassed(job.deadline)}
              >
                {isDeadlinePassed(job.deadline) ? 'Applications Closed' : 'Apply Now'}
              </button>
            </div>

            {relatedJobs.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6 mt-6">
                <h3 className="font-semibold mb-4">Related Positions</h3>
                <div className="space-y-4">
                  {relatedJobs.map((relatedJob) => (
                    <Link
                      key={relatedJob.id}
                      href={`/careers/${relatedJob.slug}`}
                      className="block p-3 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <p className="font-medium text-gray-900 hover:text-primary-600">
                        {relatedJob.title}
                      </p>
                      <p className="text-sm text-gray-500">
                        {relatedJob.location || 'Harare, Zimbabwe'}
                      </p>
                    </Link>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

JobDetailPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default JobDetailPage;
